#!/usr/bin/env python
import sys, getopt

def main(argv):
	try:
		opts, args = getopt.getopt(argv, "b:")
	except getopt.GetoptError:
		sys.exit(2)
	
	for o, a in opts:
		if o=="-b":
			sys.path.insert(0, a)
		else:
		  assert False, "Unhandled option"
		  
	from Mailman import Site
	print Site.get_listpath('')
	sys.exit(0)
	
	
if __name__ == "__main__":
	main(sys.argv[1:])
	